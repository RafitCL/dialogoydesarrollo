-- Esquema base del CMS Diálogo y Desarrollo Perú
-- Compatible con MySQL 8 / MariaDB 10.3+. Ejecutar una sola vez.

CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombres VARCHAR(100) NOT NULL DEFAULT '',
    ap_paterno VARCHAR(100) NOT NULL DEFAULT '',
    ap_materno VARCHAR(100) NOT NULL DEFAULT '',
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    rol VARCHAR(20) NOT NULL DEFAULT 'redactor',
    INDEX idx_usuarios_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS autores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombres VARCHAR(100) NOT NULL DEFAULT '',
    ap_paterno VARCHAR(100) NOT NULL DEFAULT '',
    ap_materno VARCHAR(100) NOT NULL DEFAULT '',
    nickname VARCHAR(100) NULL,
    es_nickname TINYINT(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS reportajes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(255) NOT NULL,
    resumen_corto TEXT NULL,
    desarrollo LONGTEXT NULL,
    foto_principal VARCHAR(500) NULL,
    pdf_adjunto VARCHAR(500) NULL,
    fecha_publicacion DATETIME NOT NULL,
    es_destacado TINYINT(1) NOT NULL DEFAULT 0,
    autor_id INT NULL,
    usuario_id INT NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    INDEX idx_reportajes_activo (activo),
    INDEX idx_reportajes_fecha (fecha_publicacion),
    CONSTRAINT fk_reportajes_autor FOREIGN KEY (autor_id) REFERENCES autores(id) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_reportajes_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS noticias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(255) NOT NULL,
    foto VARCHAR(500) NULL,
    link_externo VARCHAR(500) NULL,
    fecha_publicacion DATETIME NOT NULL,
    usuario_id INT NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    INDEX idx_noticias_activo (activo),
    INDEX idx_noticias_fecha (fecha_publicacion),
    CONSTRAINT fk_noticias_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS boletines (
    id INT AUTO_INCREMENT PRIMARY KEY,
    numero_boletin VARCHAR(255) NOT NULL,
    resumen TEXT NULL,
    foto_portada VARCHAR(500) NULL,
    archivo_pdf VARCHAR(500) NULL,
    fecha_publicacion DATETIME NOT NULL,
    usuario_id INT NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    INDEX idx_boletines_activo (activo),
    INDEX idx_boletines_fecha (fecha_publicacion),
    CONSTRAINT fk_boletines_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS podcasts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(255) NOT NULL,
    url_embed VARCHAR(500) NOT NULL,
    fecha_publicacion DATETIME NOT NULL,
    usuario_id INT NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    INDEX idx_podcasts_activo (activo),
    INDEX idx_podcasts_fecha (fecha_publicacion),
    CONSTRAINT fk_podcasts_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS videos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(255) NOT NULL,
    url_embed VARCHAR(500) NOT NULL,
    fecha_publicacion DATETIME NOT NULL,
    usuario_id INT NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    INDEX idx_videos_activo (activo),
    INDEX idx_videos_fecha (fecha_publicacion),
    CONSTRAINT fk_videos_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS fotos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reportaje_id INT NULL,
    boletin_id INT NULL,
    noticia_id INT NULL,
    url_foto VARCHAR(500) NOT NULL,
    imagen LONGBLOB NULL,
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;