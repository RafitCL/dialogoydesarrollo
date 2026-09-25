<?php
declare(strict_types=1);

require __DIR__ . '/config.php';

function tabla_existe(string $tabla): bool
{
    $stmt = db()->query("SHOW TABLES LIKE '" . str_replace("'", "''", $tabla) . "'");
    return $stmt->fetchColumn() !== false;
}

function columna_existe(string $tabla, string $columna): bool
{
    $stmt = db()->query("SHOW COLUMNS FROM `$tabla` LIKE '" . str_replace("'", "''", $columna) . "'");
    return $stmt->fetchColumn() !== false;
}

function ejecutar(string $sql): void
{
    db()->exec($sql);
}

$mensajes = [];
$pdo = db();

try {
    // ========== 1. USUARIOS ==========
    if (tabla_existe('usuarios')) {
        if (columna_existe('usuarios', 'nombre_completo') && !columna_existe('usuarios', 'nombres')) {
            ejecutar("ALTER TABLE usuarios
                ADD COLUMN nombres VARCHAR(100) NOT NULL DEFAULT '' AFTER id,
                ADD COLUMN ap_paterno VARCHAR(100) NOT NULL DEFAULT '' AFTER nombres,
                ADD COLUMN ap_materno VARCHAR(100) NOT NULL DEFAULT '' AFTER ap_paterno");

            $usuarios = $pdo->query('SELECT id, nombre_completo FROM usuarios')->fetchAll();
            $upd = $pdo->prepare('UPDATE usuarios SET nombres = :n, ap_paterno = :ap, ap_materno = :am WHERE id = :id');
            foreach ($usuarios as $u) {
                $partes = preg_split('/\s+/', trim((string) $u['nombre_completo']), 3);
                $partes = array_values(array_filter($partes));
                $nombres = $partes[0] ?? 'Usuario';
                $apPaterno = $partes[1] ?? 'Sin asignar';
                $apMaterno = $partes[2] ?? 'Sin asignar';
                $upd->execute(['n' => $nombres, 'ap' => $apPaterno, 'am' => $apMaterno, 'id' => (int) $u['id']]);
            }

            ejecutar('ALTER TABLE usuarios DROP COLUMN nombre_completo');
            $mensajes[] = 'usuarios: nombre_completo → nombres + ap_paterno + ap_materno';
        }

        if (columna_existe('usuarios', 'updated_at')) {
            ejecutar('ALTER TABLE usuarios DROP COLUMN updated_at');
            $mensajes[] = 'usuarios: se eliminó updated_at';
        }
    }

    // ========== 2. AUTORES ==========
    if (tabla_existe('autores')) {
        if (columna_existe('autores', 'nombre') && !columna_existe('autores', 'nombres')) {
            ejecutar("ALTER TABLE autores
                ADD COLUMN nombres VARCHAR(100) NOT NULL DEFAULT '' AFTER id,
                ADD COLUMN ap_paterno VARCHAR(100) NOT NULL DEFAULT '' AFTER nombres,
                ADD COLUMN ap_materno VARCHAR(100) NOT NULL DEFAULT '' AFTER ap_paterno,
                ADD COLUMN nickname VARCHAR(100) NULL AFTER ap_materno,
                ADD COLUMN es_nickname TINYINT(1) NOT NULL DEFAULT 0 AFTER nickname");

            $autores = $pdo->query('SELECT id, nombre, slug FROM autores')->fetchAll();
            $upd = $pdo->prepare('UPDATE autores SET nombres = :n, ap_paterno = :ap, ap_materno = :am, nickname = :nick, es_nickname = :es WHERE id = :id');
            foreach ($autores as $a) {
                $partes = preg_split('/\s+/', trim((string) $a['nombre']), 3);
                $partes = array_values(array_filter($partes));
                $nombres = $partes[0] ?? 'Autor';
                $apPaterno = $partes[1] ?? 'Sin asignar';
                $apMaterno = $partes[2] ?? 'Sin asignar';
                $upd->execute([
                    'n' => $nombres,
                    'ap' => $apPaterno,
                    'am' => $apMaterno,
                    'nick' => (string) $a['nombre'],
                    'es' => 0,
                    'id' => (int) $a['id'],
                ]);
            }

            ejecutar('ALTER TABLE autores DROP COLUMN nombre, DROP COLUMN slug');
            $mensajes[] = 'autores: nombre+slug → nombres + ap_paterno + ap_materno + nickname + es_nickname';
        }

        foreach (['created_at', 'updated_at'] as $col) {
            if (columna_existe('autores', $col)) {
                ejecutar("ALTER TABLE autores DROP COLUMN $col");
                $mensajes[] = "autores: se eliminó $col";
            }
        }
    }

    // ========== 3. REPORTAJES ==========
    if (tabla_existe('reportajes')) {
        if (columna_existe('reportajes', 'slug')) {
            ejecutar('ALTER TABLE reportajes DROP COLUMN slug');
            $mensajes[] = 'reportajes: se eliminó slug';
        }
        if (!columna_existe('reportajes', 'es_destacado')) {
            ejecutar('ALTER TABLE reportajes ADD COLUMN es_destacado TINYINT(1) NOT NULL DEFAULT 0 AFTER fecha_publicacion');
            $mensajes[] = 'reportajes: se agregó es_destacado';
        }
    }

    // ========== 4. FOTOS (unificada con FK a reportajes/boletines/noticias) ==========
    if (!tabla_existe('fotos')) {
        ejecutar("CREATE TABLE fotos (
            id INT AUTO_INCREMENT PRIMARY KEY,
            reportaje_id INT NULL,
            boletin_id INT NULL,
            noticia_id INT NULL,
            url_foto VARCHAR(500) NOT NULL,
            orden INT NOT NULL DEFAULT 0,
            descripcion VARCHAR(500) NULL,
            INDEX idx_fotos_reportaje (reportaje_id),
            INDEX idx_fotos_boletin (boletin_id),
            INDEX idx_fotos_noticia (noticia_id),
            CONSTRAINT fk_fotos_reportaje FOREIGN KEY (reportaje_id) REFERENCES reportajes(id) ON DELETE CASCADE ON UPDATE CASCADE,
            CONSTRAINT fk_fotos_boletin FOREIGN KEY (boletin_id) REFERENCES boletines(id) ON DELETE CASCADE ON UPDATE CASCADE,
            CONSTRAINT fk_fotos_noticia FOREIGN KEY (noticia_id) REFERENCES noticias(id) ON DELETE CASCADE ON UPDATE CASCADE,
            CONSTRAINT chk_fotos_entidad CHECK (
                (reportaje_id IS NOT NULL) + (boletin_id IS NOT NULL) + (noticia_id IS NOT NULL) = 1
            )
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $mensajes[] = 'fotos: tabla creada con llaves foráneas a reportajes, boletines y noticias';
    } elseif (columna_existe('fotos', 'tipo_entidad')) {
        ejecutar('ALTER TABLE fotos
            ADD COLUMN reportaje_id INT NULL AFTER id,
            ADD COLUMN boletin_id INT NULL AFTER reportaje_id,
            ADD COLUMN noticia_id INT NULL AFTER boletin_id');

        $pdo->exec("UPDATE fotos SET reportaje_id = entidad_id WHERE tipo_entidad = 'reportaje'");
        $pdo->exec("UPDATE fotos SET boletin_id = entidad_id WHERE tipo_entidad = 'boletin'");
        $pdo->exec("UPDATE fotos SET noticia_id = entidad_id WHERE tipo_entidad = 'noticia'");

        ejecutar('ALTER TABLE fotos ADD INDEX idx_fotos_reportaje (reportaje_id)');
        ejecutar('ALTER TABLE fotos ADD INDEX idx_fotos_boletin (boletin_id)');
        ejecutar('ALTER TABLE fotos ADD INDEX idx_fotos_noticia (noticia_id)');

        ejecutar('ALTER TABLE fotos
            ADD CONSTRAINT fk_fotos_reportaje FOREIGN KEY (reportaje_id) REFERENCES reportajes(id) ON DELETE CASCADE ON UPDATE CASCADE,
            ADD CONSTRAINT fk_fotos_boletin FOREIGN KEY (boletin_id) REFERENCES boletines(id) ON DELETE CASCADE ON UPDATE CASCADE,
            ADD CONSTRAINT fk_fotos_noticia FOREIGN KEY (noticia_id) REFERENCES noticias(id) ON DELETE CASCADE ON UPDATE CASCADE');

        ejecutar('ALTER TABLE fotos DROP INDEX idx_entidad, DROP INDEX idx_orden, DROP COLUMN tipo_entidad, DROP COLUMN entidad_id');

        ejecutar('ALTER TABLE fotos ADD CONSTRAINT chk_fotos_entidad CHECK (
            (reportaje_id IS NOT NULL) + (boletin_id IS NOT NULL) + (noticia_id IS NOT NULL) = 1
        )');

        $mensajes[] = 'fotos: estructura migrada a columnas reportaje_id / boletin_id / noticia_id con llaves foráneas';
    }

    if (tabla_existe('reportajes_fotos')) {
        $pdo->exec("INSERT INTO fotos (reportaje_id, url_foto, orden, descripcion)
            SELECT reportaje_id, foto, orden, titulo FROM reportajes_fotos");
        ejecutar('DROP TABLE reportajes_fotos');
        $mensajes[] = 'fotos: datos de reportajes_fotos migrados y tabla eliminada';
    }

    if (tabla_existe('boletines_fotos')) {
        $pdo->exec("INSERT INTO fotos (boletin_id, url_foto, orden, descripcion)
            SELECT boletin_id, foto, orden, titulo FROM boletines_fotos");
        ejecutar('DROP TABLE boletines_fotos');
        $mensajes[] = 'fotos: datos de boletines_fotos migrados y tabla eliminada';
    }

    // ========== 5. NOTICIAS ==========
    if (tabla_existe('noticias_recientes') && !tabla_existe('noticias')) {
        ejecutar('RENAME TABLE noticias_recientes TO noticias');
        $mensajes[] = 'noticias_recientes → noticias';
    }
    if (tabla_existe('noticias')) {
        if (!columna_existe('noticias', 'foto')) {
            ejecutar('ALTER TABLE noticias ADD COLUMN foto VARCHAR(500) NULL AFTER titulo');
            $mensajes[] = 'noticias: se agregó foto';
        }
        foreach (['created_at', 'updated_at'] as $col) {
            if (columna_existe('noticias', $col)) {
                ejecutar("ALTER TABLE noticias DROP COLUMN $col");
                $mensajes[] = "noticias: se eliminó $col";
            }
        }
    }

    // ========== 6. BOLETINES, PODCASTS, VIDEOS ==========
    foreach (['boletines', 'podcasts', 'videos'] as $tabla) {
        if (!tabla_existe($tabla)) {
            continue;
        }
        foreach (['created_at', 'updated_at'] as $col) {
            if (columna_existe($tabla, $col)) {
                ejecutar("ALTER TABLE $tabla DROP COLUMN $col");
                $mensajes[] = "$tabla: se eliminó $col";
            }
        }
    }

    // ========== 7. ELIMINACIÓN DEL MÓDULO ESPECIALES ==========
    if (tabla_existe('fotos') && columna_existe('fotos', 'especial_id')) {
        ejecutar('DELETE FROM fotos WHERE especial_id IS NOT NULL');
        if (columna_existe('fotos', 'especial_id')) {
            try {
                $fk = $pdo->query("SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE
                    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'fotos'
                    AND COLUMN_NAME = 'especial_id' AND REFERENCED_TABLE_NAME = 'especiales'")->fetchColumn();
                if ($fk !== false && $fk !== '') {
                    ejecutar('ALTER TABLE fotos DROP FOREIGN KEY `' . $fk . '`');
                }
            } catch (Throwable $e) {
                // continúa: puede no existir la FK
            }
            try {
                ejecutar("ALTER TABLE fotos DROP CONSTRAINT chk_fotos_entidad");
            } catch (Throwable $e) {
                // continúa: puede no existir el CHECK
            }
            try {
                ejecutar('ALTER TABLE fotos DROP INDEX idx_fotos_especial');
            } catch (Throwable $e) {
                // continúa: puede no existir el índice
            }
            ejecutar('ALTER TABLE fotos DROP COLUMN especial_id');
            ejecutar("ALTER TABLE fotos ADD CONSTRAINT chk_fotos_entidad CHECK (
                (reportaje_id IS NOT NULL) + (boletin_id IS NOT NULL) + (noticia_id IS NOT NULL) = 1
            )");
            $mensajes[] = 'fotos: se eliminó especial_id y su FK';
        }
    }

    if (tabla_existe('especiales')) {
        ejecutar('DROP TABLE especiales');
        $mensajes[] = 'especiales: tabla eliminada';
    }

    $mensajes[] = 'Migración completada correctamente.';
} catch (Throwable $e) {
    $mensajes[] = 'ERROR: ' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="es">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Migración de base de datos</title>
    <style>
      body { font-family: system-ui, sans-serif; background: #f3f6f8; padding: 40px 20px; }
      .card { max-width: 720px; margin: 0 auto; background: #fff; border-radius: 12px; padding: 30px; box-shadow: 0 10px 30px rgba(15, 23, 42, .08); }
      h1 { font-size: 20px; margin-top: 0; }
      ul { padding-left: 20px; }
      li { padding: 6px 0; font-size: 14px; }
      a { display: inline-block; margin-top: 16px; color: #2563eb; text-decoration: none; font-weight: 600; }
    </style>
  </head>
  <body>
    <div class="card">
      <h1>Migración de base de datos</h1>
      <ul>
        <?php foreach ($mensajes as $m): ?>
          <li><?= htmlspecialchars($m) ?></li>
        <?php endforeach; ?>
      </ul>
      <a href="index.php">&larr; Volver al panel</a>
    </div>
  </body>
</html>