<?php
declare(strict_types=1);

if (!isset($pagina)) {
    $pagina = '';
}

require_once dirname(__DIR__) . '/admin/config.php';

if (!function_exists('e')) {
    function e(?string $s): string
    {
        return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('fecha_comun')) {
    function fecha_comun(?string $fecha): string
    {
        if ($fecha === null || $fecha === '') {
            return '—';
        }
        $ts = strtotime($fecha);
        $meses = [1 => 'Ene', 2 => 'Feb', 3 => 'Mar', 4 => 'Abr', 5 => 'May', 6 => 'Jun', 7 => 'Jul', 8 => 'Ago', 9 => 'Set', 10 => 'Oct', 11 => 'Nov', 12 => 'Dic'];
        return $meses[(int) date('n', $ts)] . ' ' . date('j', $ts) . ', ' . date('Y', $ts);
    }
}

if (!function_exists('resumen')) {
    function resumen(string $texto, int $max = 160): string
    {
        $texto = trim(strip_tags($texto));
        if (mb_strlen($texto) <= $max) {
            return $texto;
        }
        return rtrim(mb_substr($texto, 0, $max), " \t\n\r\0\x0B,") . '…';
    }
}

if (!function_exists('img_publica')) {
    function img_publica(?string $ruta, string $fallback): string
    {
        if (is_string($ruta) && $ruta !== '') {
            $archivo = dirname(__DIR__) . '/' . $ruta;
            if (is_file($archivo) && filesize($archivo) > 200) {
                return '../' . $ruta;
            }
        }
        return $fallback;
    }
}

if (!function_exists('img_foto')) {
    function img_foto(string $tabla, int $id, string $fallback): string
    {
        if (!in_array($tabla, ['reportaje', 'noticia', 'boletin'], true)) {
            return $fallback;
        }
        $col = $tabla . '_id';
        $st = db()->prepare("SELECT id FROM fotos WHERE $col = :id AND imagen IS NOT NULL AND LENGTH(imagen) > 0 ORDER BY orden ASC, id ASC LIMIT 1");
        $st->execute(['id' => $id]);
        $f = $st->fetch();
        if ($f !== false) {
            return '../assets/images/imagen.php?id=' . (int) $f['id'];
        }
        return $fallback;
    }
}

if (!function_exists('mes_anio')) {
    function mes_anio(string $ym): string
    {
        $nombres = [1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril', 5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto', 9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'];
        $partes = explode('-', $ym);
        if (count($partes) !== 2) {
            return $ym;
        }
        return ($nombres[(int) $partes[1]] ?? $partes[1]) . ' ' . $partes[0];
    }
}

if (!function_exists('media_embed')) {
    function media_embed(string $url): array
    {
        if ($url === '' || $url === '#') {
            return ['', ''];
        }
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        if (str_contains($host, 'spotify')) {
            if (preg_match('#/(episode|track|album|playlist|show)/([A-Za-z0-9]+)#i', $url, $m)) {
                return ['https://open.spotify.com/embed/' . strtolower($m[1]) . '/' . $m[2], 'audio'];
            }
            if (str_contains($url, '/embed/')) {
                return [$url, 'audio'];
            }
            return ['', ''];
        }
        if (preg_match('/(?:youtube\.com\/(?:watch\?(?:.*&)?v=|embed\/|shorts\/|live\/)|youtu\.be\/)([A-Za-z0-9_-]{11})/', $url, $m)) {
            return ['https://www.youtube.com/embed/' . $m[1], 'video'];
        }
        if (str_starts_with($url, 'https://www.youtube.com/embed/')) {
            return [$url, 'video'];
        }
        if (str_contains($host, 'spotify')) {
            if (str_contains($url, '/embed/')) {
                return [$url, 'audio'];
            }
        }
        return [$url, str_contains($host, 'spotify') ? 'audio' : 'video'];
    }
}

if (!function_exists('video_thumb')) {
    function video_thumb(string $url, string $fallback): string
    {
        if (preg_match('/(?:youtube\.com\/(?:watch\?(?:.*&)?v=|embed\/|shorts\/|live\/)|youtu\.be\/)([A-Za-z0-9_-]{11})/', $url, $m)) {
            return 'https://i.ytimg.com/vi/' . $m[1] . '/hqdefault.jpg';
        }
        return $fallback;
    }
}

if (!function_exists('nav_active')) {
    function nav_active(string $pagina, string $seccion): string
    {
        return $pagina === $seccion ? ' active' : '';
    }
}

if (!isset($descripcion) || trim((string) $descripcion) === '') {
    $descripcion = 'Diálogo y Desarrollo Perú - espacio de periodismo independiente que visibiliza las acciones de diálogo en el país.';
}

if (!isset($canonical) || $canonical === '') {
    $rutaActual = (string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH);
    if ($rutaActual === '') {
        $rutaActual = '/';
    }
    $canonical = rtrim(SITE_URL, '/') . '/' . ltrim($rutaActual, '/');
}

if (!isset($ogTipo) || $ogTipo === '') {
    $ogTipo = 'website';
}

if (!isset($ogImagen) || $ogImagen === '') {
    $ogImagen = rtrim(SITE_URL, '/') . '/assets/images/logo.png';
}

$tituloSeo = ($titulo ?? 'DDP Noticias') . ' - Diálogo y Desarrollo Perú';
?>
<!DOCTYPE html>
<html lang="es">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="description" content="<?= e($descripcion) ?>" />
    <link rel="canonical" href="<?= e($canonical) ?>" />
    <link rel="icon" type="image/png" href="../assets/images/favicon.png" />
    <title><?= e($tituloSeo) ?></title>
    <meta property="og:type" content="<?= e($ogTipo) ?>" />
    <meta property="og:site_name" content="Diálogo y Desarrollo Perú" />
    <meta property="og:locale" content="es_PE" />
    <meta property="og:title" content="<?= e($tituloSeo) ?>" />
    <meta property="og:description" content="<?= e($descripcion) ?>" />
    <meta property="og:url" content="<?= e($canonical) ?>" />
    <meta property="og:image" content="<?= e($ogImagen) ?>" />
    <meta name="twitter:card" content="summary_large_image" />
    <meta name="twitter:title" content="<?= e($tituloSeo) ?>" />
    <meta name="twitter:description" content="<?= e($descripcion) ?>" />
    <meta name="twitter:image" content="<?= e($ogImagen) ?>" />
    <link href="https://fonts.googleapis.com/css?family=Cabin:400,500,600&amp;subset=latin-ext,vietnamese" rel="stylesheet" />
    <link rel="stylesheet" href="../assets/css/style-starter.css" />
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "Organization",
      "@id": "<?= rtrim(SITE_URL, '/') ?>/#organization",
      "name": "Diálogo y Desarrollo Perú",
      "alternateName": "DDP Noticias",
      "url": "<?= rtrim(SITE_URL, '/') ?>/",
      "logo": {
        "@type": "ImageObject",
        "url": "<?= rtrim(SITE_URL, '/') ?>/assets/images/logo.png"
      },
      "sameAs": [
        "https://www.facebook.com/DialogoyDesarrolloPeru",
        "https://www.tiktok.com/@dialogo.y.desarrollo",
        "https://www.instagram.com/dialogo.y.desarrollo/"
      ]
    }
    </script>
  </head>
  <body>
    <header id="site-header" class="fixed-top">
      <div class="container">
        <nav class="navbar navbar-expand-lg stroke">
          <a class="navbar-brand" href="../index.php">
            <img src="../assets/images/logo.png" alt="Diálogo y Desarrollo Perú" title="Diálogo y Desarrollo Perú" style="height: 75px;" />
          </a>
          <button class="navbar-toggler collapsed bg-gradient" type="button" data-toggle="collapse"
            data-target="#navbarTogglerDemo02" aria-controls="navbarTogglerDemo02" aria-expanded="false"
            aria-label="Toggle navigation">
            <span class="navbar-toggler-icon fa icon-expand fa-bars"></span>
            <span class="navbar-toggler-icon fa icon-close fa-times"></span>
          </button>
          <div class="collapse navbar-collapse" id="navbarTogglerDemo02">
            <ul class="navbar-nav ml-auto">
              <li class="nav-item<?= nav_active($pagina, 'inicio') ?>">
                <a class="nav-link" href="../index.php">Inicio</a>
              </li>
              <li class="nav-item">
                <a class="nav-link" href="../index.php#actualidad">Actualidad</a>
              </li>
              <li class="nav-item<?= nav_active($pagina, 'reportajes') ?>">
                <a class="nav-link" href="reportajes.php">Reportajes</a>
              </li>
              <li class="nav-item<?= nav_active($pagina, 'podcast') ?>">
                <a class="nav-link" href="podcast.php">Podcast</a>
              </li>
              <li class="nav-item<?= nav_active($pagina, 'video') ?>">
                <a class="nav-link" href="video.php">Video</a>
              </li>
              <li class="nav-item<?= nav_active($pagina, 'boletines') ?>">
                <a class="nav-link" href="boletines.php">Boletín NTEP</a>
              </li>
              <li class="nav-item<?= nav_active($pagina, 'alianzas') ?>">
                <a class="nav-link" href="alianzas.php">Alianzas</a>
              </li>
              <li class="nav-item<?= nav_active($pagina, 'nosotros') ?>">
                <a class="nav-link" href="nosotros.php">Sobre D&D</a>
              </li>
              <li class="ml-2">
                <a href="contacto.php" class="btn btn-style btn-outline-secondary">Contacto</a>
              </li>
            </ul>
          </div>
        </nav>
      </div>
    </header>