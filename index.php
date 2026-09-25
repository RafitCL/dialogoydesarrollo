<?php
declare(strict_types=1);

require __DIR__ . '/admin/config.php';

function e(?string $s): string
{
    return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
}

function fecha_comun(?string $fecha): string
{
    if ($fecha === null || $fecha === '') {
        return '—';
    }
    $ts = strtotime($fecha);
    $meses = [1 => 'Ene', 2 => 'Feb', 3 => 'Mar', 4 => 'Abr', 5 => 'May', 6 => 'Jun', 7 => 'Jul', 8 => 'Ago', 9 => 'Set', 10 => 'Oct', 11 => 'Nov', 12 => 'Dic'];
    return $meses[(int) date('n', $ts)] . ' ' . date('j', $ts) . ', ' . date('Y', $ts);
}

function resumen(string $texto, int $max = 160): string
{
    $texto = trim(strip_tags($texto));
    if (mb_strlen($texto) <= $max) {
        return $texto;
    }
    return rtrim(mb_substr($texto, 0, $max), " \t\n\r\0\x0B,") . '…';
}

function img_portada(?string $ruta, string $fallback): string
{
    if (is_string($ruta) && $ruta !== '' && str_starts_with($ruta, 'uploads/')) {
        $archivo = __DIR__ . '/' . $ruta;
        if (is_file($archivo) && filesize($archivo) > 200) {
            return 'uploads/' . basename($ruta);
        }
        return $fallback;
    }
    return $fallback;
}

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
    if (str_contains($host, 'spotify') && str_contains($url, '/embed/')) {
        return [$url, 'audio'];
    }
    return [$url, str_contains($host, 'spotify') ? 'audio' : 'video'];
}

function video_thumb(string $url, string $fallback): string
{
    if (preg_match('/(?:youtube\.com\/(?:watch\?(?:.*&)?v=|embed\/|shorts\/|live\/)|youtu\.be\/)([A-Za-z0-9_-]{11})/', $url, $m)) {
        return 'https://i.ytimg.com/vi/' . $m[1] . '/hqdefault.jpg';
    }
    return $fallback;
}

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
        return 'assets/images/imagen.php?id=' . (int) $f['id'];
    }
    return $fallback;
}

$reportajes = db()->query("SELECT r.*, a.nombres AS a_nombres, a.ap_paterno AS a_ap, a.ap_materno AS a_am, a.nickname AS a_nick, a.es_nickname AS a_es
    FROM reportajes r
    LEFT JOIN autores a ON a.id = r.autor_id
    WHERE r.activo = 1
    ORDER BY r.es_destacado DESC, r.fecha_publicacion DESC")->fetchAll();

$destacado = null;
foreach ($reportajes as $r) {
    if ((int) $r['es_destacado'] === 1) {
        $destacado = $r;
        break;
    }
}
if ($destacado === null && !empty($reportajes)) {
    $destacado = $reportajes[0];
}

$noticias = db()->query('SELECT * FROM noticias WHERE activo = 1 ORDER BY fecha_publicacion DESC LIMIT 3')->fetchAll();
$boletines = db()->query('SELECT * FROM boletines WHERE activo = 1 ORDER BY fecha_publicacion DESC LIMIT 3')->fetchAll();
$videos = db()->query('SELECT * FROM videos WHERE activo = 1 ORDER BY fecha_publicacion DESC LIMIT 4')->fetchAll();
$destacadoId = $destacado !== null ? (int) $destacado['id'] : 0;
$canonicalHome = rtrim(SITE_URL, '/') . '/';
$podcastsHome = db()->query('SELECT * FROM podcasts WHERE activo = 1 ORDER BY fecha_publicacion DESC LIMIT 6')->fetchAll();
$podcastsHome = array_values(array_filter($podcastsHome, fn($p) => media_embed((string) ($p['url_embed'] ?? ''))[0] !== ''));
$videosHome = db()->query('SELECT * FROM videos WHERE activo = 1 ORDER BY fecha_publicacion DESC LIMIT 6')->fetchAll();
$videosHome = array_values(array_filter($videosHome, fn($v) => media_embed((string) ($v['url_embed'] ?? ''))[0] !== ''));
$canonicalHome = rtrim(SITE_URL, '/') . '/';
$descripcionHome = 'Diálogo y Desarrollo Perú - espacio de periodismo independiente que visibiliza las acciones de diálogo en el país.';
?>
<!DOCTYPE html>
<html lang="es">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="description" content="<?= e($descripcionHome) ?>" />
    <link rel="canonical" href="<?= e($canonicalHome) ?>" />
    <link rel="icon" type="image/png" href="assets/images/favicon.png" />
    <title>DDP Noticias - Diálogo y Desarrollo Perú</title>
    <meta property="og:type" content="website" />
    <meta property="og:site_name" content="Diálogo y Desarrollo Perú" />
    <meta property="og:locale" content="es_PE" />
    <meta property="og:title" content="DDP Noticias - Diálogo y Desarrollo Perú" />
    <meta property="og:description" content="<?= e($descripcionHome) ?>" />
    <meta property="og:url" content="<?= e($canonicalHome) ?>" />
    <meta property="og:image" content="<?= e(rtrim(SITE_URL, '/') . '/assets/images/logo.png') ?>" />
    <meta name="twitter:card" content="summary_large_image" />
    <meta name="twitter:title" content="DDP Noticias - Diálogo y Desarrollo Perú" />
    <meta name="twitter:description" content="<?= e($descripcionHome) ?>" />
    <meta name="twitter:image" content="<?= e(rtrim(SITE_URL, '/') . '/assets/images/logo.png') ?>" />
    <link href="https://fonts.googleapis.com/css?family=Cabin:400,500,600&amp;subset=latin-ext,vietnamese" rel="stylesheet" />
    <link rel="stylesheet" href="assets/css/style-starter.css" />
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "WebSite",
      "name": "Diálogo y Desarrollo Perú",
      "alternateName": "DDP Noticias",
      "url": "<?= e($canonicalHome) ?>"
    }
    </script>
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "Organization",
      "@id": "<?= e(rtrim(SITE_URL, '/') . '/#organization') ?>",
      "name": "Diálogo y Desarrollo Perú",
      "alternateName": "DDP Noticias",
      "url": "<?= e($canonicalHome) ?>",
      "logo": {
        "@type": "ImageObject",
        "url": "<?= e(rtrim(SITE_URL, '/') . '/assets/images/logo.png') ?>"
      },
      "sameAs": [
        "https://www.facebook.com/DialogoyDesarrolloPeru",
        "https://www.tiktok.com/@dialogo.y.desarrollo",
        "https://www.instagram.com/dialogo.y.desarrollo/"
      ]
    }
    </script>
    <style>
      .grids5-info {
        display: flex;
        flex-direction: column;
      }
      .grids5-info .img-uniform {
        width: 100%;
        height: 220px;
        object-fit: cover;
        object-position: center;
        display: block;
      }
      .grids5-info .blog-info {
        flex: 1;
        display: flex;
        flex-direction: column;
      }
      .grids5-info .blog-info .btn {
        margin-top: auto;
      }
      .owl-media .item {
        margin-bottom: 6px;
      }
      .owl-media iframe {
        width: 100%;
      }
      .owl-media .owl-nav button.owl-prev,
      .owl-media .owl-nav button.owl-next {
        width: 42px;
        height: 42px;
        line-height: 42px;
        font-size: 26px;
        margin: 0 8px;
        background: #8a93a6;
        color: #fff;
        border-radius: 50%;
        transition: all 0.25s ease;
      }
      .owl-media .owl-nav button span {
        font-size: 26px;
        line-height: inherit;
        display: inline-block;
        color: #fff;
      }
      .owl-media .owl-nav button.owl-prev:hover,
      .owl-media .owl-nav button.owl-next:hover {
        background: #a4adbd;
        color: #fff;
      }
      .owl-media .owl-dots {
        position: static;
        margin-top: 22px;
      }
      .owl-media--equipo .owl-nav {
        position: absolute;
        left: 0;
        right: 0;
        top: 40%;
        display: flex;
        justify-content: space-between;
        padding: 0 10px;
        margin-top: 0;
        pointer-events: none;
      }
      .owl-media--equipo .owl-nav button {
        pointer-events: auto;
      }
      .w3l-team .owl-media .owl-nav {
        display: block;
      }
      .owl-media--equipo .media-frame {
        position: relative;
        width: 100%;
        padding-bottom: 56.25%;
        height: 0;
        overflow: hidden;
        background: #0b0f19;
      }
      .owl-media--equipo .media-frame .embed-responsive {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        padding-bottom: 0;
      }
      .owl-media--equipo .media-frame .embed-responsive-item {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
      }
      .owl-media--equipo .media-frame > iframe {
        position: absolute;
        top: 50%;
        left: 0;
        width: 100%;
        height: 152px;
        transform: translateY(-50%);
        border: 0;
      }
    </style>
  </head>
  <body>
    <!-- header -->
    <header id="site-header" class="fixed-top">
      <div class="container">
        <nav class="navbar navbar-expand-lg stroke">
          <a class="navbar-brand" href="index.php">
            <img src="assets/images/logo.png" alt="Diálogo y Desarrollo Perú" title="Diálogo y Desarrollo Perú" style="height: 75px;" />
          </a>
          <button class="navbar-toggler collapsed bg-gradient" type="button" data-toggle="collapse"
            data-target="#navbarTogglerDemo02" aria-controls="navbarTogglerDemo02" aria-expanded="false"
            aria-label="Toggle navigation">
            <span class="navbar-toggler-icon fa icon-expand fa-bars"></span>
            <span class="navbar-toggler-icon fa icon-close fa-times"></span>
          </button>
          <div class="collapse navbar-collapse" id="navbarTogglerDemo02">
            <ul class="navbar-nav ml-auto">
              <li class="nav-item active">
                <a class="nav-link" href="index.php">Inicio</a>
              </li>
              <li class="nav-item">
                <a class="nav-link" href="#actualidad">Actualidad</a>
              </li>
              <li class="nav-item">
                <a class="nav-link" href="pages/reportajes.php">Reportajes</a>
              </li>
              <li class="nav-item">
                <a class="nav-link" href="pages/podcast.php">Podcast</a>
              </li>
              <li class="nav-item">
                <a class="nav-link" href="pages/video.php">Video</a>
              </li>
              <li class="nav-item">
                <a class="nav-link" href="pages/boletines.php">Boletín NTEP</a>
              </li>
              <li class="nav-item">
                <a class="nav-link" href="pages/alianzas.php">Alianzas</a>
              </li>
              <li class="nav-item">
                <a class="nav-link" href="pages/nosotros.php">Sobre D&D</a>
              </li>
              <li class="ml-2">
                <a href="pages/contacto.php" class="btn btn-style btn-outline-secondary">Contacto</a>
              </li>
            </ul>
          </div>
        </nav>
      </div>
    </header>
    <!-- //header -->

    <section class="breadcrumb-area py-sm-5 py-4" style="margin-top: 85px;">
      <div class="container">
        <div class="breadcrumb-contents">
          <a href="pages/reportajes.php"><h2 class="title-big">Reportajes</h2></a>
        </div>
      </div>
    </section>

    <?php if ($destacado !== null): ?>
      <section class="w3l-video w3l-homeblock3" id="video">
        <div class="container-fluid">
          <div class="video-grids-info row">
            <div class="video-gd-right col-lg-6 p-0">
              <div class="position-relative">
                <a href="pages/reportaje.php?id=<?= (int) $destacado['id'] ?>">
                  <img src="<?= e(img_foto('reportaje', (int) $destacado['id'], 'assets/images/video.jpg')) ?>" alt="<?= e($destacado['titulo'] ?? '') ?>" class="img-fluid" />
                </a>
              </div>
            </div>
            <div class="video-gd-left col-lg-6 p-lg-5 p-4 align-self">
              <div class="p-xl-4 p-0 video-wrap">
                <h5><?= e(fecha_comun($destacado['fecha_publicacion'] ?? null)) ?></h5>
                <?php if (!empty($destacado['resumen_corto'])): ?>
                  <h6 class="text-uppercase" style="color: #92400e; letter-spacing: 2px; font-size: 0.85rem;">Especial</h6>
                <?php endif; ?>
                <h3 class="title-big text-left mb-4">
                  <a href="pages/reportaje.php?id=<?= (int) $destacado['id'] ?>"><?= e($destacado['titulo'] ?? '') ?></a>
                </h3>
                <p class="mb-0">
                  <?= e(resumen($destacado['resumen_corto'] ?? $destacado['desarrollo'] ?? '', 250)) ?>
                </p>
                <?php if (!empty($destacado['pdf_adjunto'])): ?>
                  <a href="<?= e(img_portada($destacado['pdf_adjunto'], '#contenido')) ?>" class="btn mt-4 p-0" target="_blank">
                    Descargar PDF <span class="fa fa-arrow-right"></span>
                  </a>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>
      </section>
    <?php endif; ?>

    <div id="reportajes" class="grids-block-5 py-1">
      <section class="py-lg-4 py-md-3">
        <div class="container">
          <div class="row">
            <?php $cnt = 0; ?>
            <?php foreach ($reportajes as $r): ?>
              <?php if ($destacado !== null && (int) $r['id'] === (int) $destacado['id']) continue; ?>
              <?php if ($cnt >= 3) break; ?>
              <?php $cnt++; ?>
              <div class="col-lg-4 col-md-6 grids5-info mt-5">
                <a href="pages/reportaje.php?id=<?= (int) $r['id'] ?>" class="d-block">
                  <img src="<?= e(img_foto('reportaje', (int) $r['id'], 'assets/images/reportaje-' . (['28-08-26', '18-08-26', '12-08-26'][($cnt - 1)] ?? '28-08-26') . '.jpg')) ?>" alt="<?= e($r['titulo'] ?? '') ?>" class="img-fluid img-uniform" loading="lazy" />
                </a>
                <div class="blog-info">
                  <h5><?= e(fecha_comun($r['fecha_publicacion'] ?? null)) ?></h5>
                  <h4><a href="pages/reportaje.php?id=<?= (int) $r['id'] ?>" class="d-block"><?= e($r['titulo'] ?? '') ?></a></h4>
                  <a href="pages/reportaje.php?id=<?= (int) $r['id'] ?>" class="btn mt-4 p-0">Leer <span class="fa fa-arrow-right"></span></a>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
          <div class="pagination">
            <ul>
              <li><a href="pages/reportajes.php">Ver todos</a></li>
            </ul>
          </div>
        </div>
      </section>
    </div>

    <!-- Noticias Recientes -->
    <section class="breadcrumb-area py-sm-5 py-1">
      <div class="container">
        <div class="breadcrumb-contents">
          <h2 class="title-big" id="actualidad">Noticias Recientes</h2>
        </div>
      </div>
    </section>
    <div class="grids-block-5 py-5">
      <section class="py-lg-4 py-md-3">
        <div class="container">
          <div class="row">
            <?php if (empty($noticias)): ?>
              <div class="col-12">
                <p class="text-muted">Aún no hay noticias publicadas.</p>
              </div>
            <?php else: ?>
              <?php foreach ($noticias as $i => $n): ?>
                <div class="col-lg-4 col-md-6 grids5-info <?= $i === 2 ? 'mt-5 mt-lg-0' : '' ?>">
                  <a target="_blank" href="<?= e($n['link_externo'] ?? '#') ?>" class="d-block">
                    <img src="<?= e(img_foto('noticia', (int) $n['id'], 'assets/images/nota-facebook-' . (['21-11-25', '21-11-25b', '20-11-25'][$i] ?? '20-11-25') . '.png')) ?>" alt="<?= e($n['titulo'] ?? 'Noticia') ?>" class="img-fluid img-uniform" loading="lazy" />
                  </a>
                  <div class="blog-info">
                    <h5><?= e(fecha_comun($n['fecha_publicacion'] ?? null)) ?></h5>
                    <h4>
                      <a target="_blank" href="<?= e($n['link_externo'] ?? '#') ?>" class="d-block"><?= e($n['titulo'] ?? '') ?></a>
                    </h4>
                    <a target="_blank" href="<?= e($n['link_externo'] ?? '#') ?>" class="btn mt-4 p-0">Leer <span class="fa fa-arrow-right"></span></a>
                  </div>
                </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
          <div class="pagination">
            <ul>
              <li><a target="_blank" href="https://www.facebook.com/DialogoyDesarrolloPeru">Ver todos</a></li>
            </ul>
          </div>
        </div>
      </section>
    </div>
    <!-- //Noticias Recientes -->

    <!-- Boletín NTEP -->
    <?php $ultimoBoletin = $boletines[0] ?? null; ?>
    <?php if ($ultimoBoletin !== null): ?>
      <?php
        $linkBoletin = img_portada($ultimoBoletin['archivo_pdf'] ?? '', '');
        if ($linkBoletin === '') {
            $linkBoletin = img_foto('boletin', (int) $ultimoBoletin['id'], 'assets/images/boletin-ntep-45.png');
        }
      ?>
      <section class="w3l-homeblock5 py-0" id="boletines">
        <div class="container py-lg-5 py-4">
          <div class="row">
            <div class="col-lg-8 align-self">
              <h3 class="title-big mb-4">Boletín NTEP <?= e($ultimoBoletin['numero_boletin'] ?? '') ?></h3>
              <p class=""><?= e(resumen($ultimoBoletin['resumen'] ?? '', 220)) ?></p>
              <div class="row mt-sm-4 mt-2 px-3">
                <div class="col-6 p-0">
                  <span><?= e($ultimoBoletin['numero_boletin'] ?? '') ?></span>
                  <h4><?= e(fecha_comun($ultimoBoletin['fecha_publicacion'] ?? null)) ?></h4>
                </div>
                <div class="col-6 p-0">
                  <span>
                    <a target="_blank" href="<?= e($linkBoletin) ?>" class="facebook">
                      <span class="fa fa-download fa-2x" style="color: #dc3545;"></span>
                    </a>
                  </span>
                  <h4>Ver Boletín</h4>
                </div>
              </div>
              <div class="mt-4">
                <a href="pages/boletines.php" class="btn btn-style btn-primary">Ver todos</a>
              </div>
            </div>
            <div class="col-lg-4 mt-lg-0 mt-4">
              <img src="<?= e(img_foto('boletin', (int) $ultimoBoletin['id'], 'assets/images/boletin-ntep-45.png')) ?>" class="img-fluid radius-image" alt="<?= e($ultimoBoletin['numero_boletin'] ?? 'Boletín NTEP') ?>" loading="lazy" />
            </div>
          </div>
        </div>
      </section>
    <?php endif; ?>
    <!-- //Boletín NTEP -->

    <!-- Podcast -->
    <section class="w3l-homeblock5 py-0" id="podcast">
      <div class="container py-lg-5 py-4">
        <div class="text-center mb-4">
          <a href="pages/podcast.php"><h3 class="title-big mb-0">Podcast</h3></a>
        </div>
        <div class="owl-media owl-media--equipo owl-carousel owl-theme">
          <?php if (empty($podcastsHome)): ?>
            <div class="item">
              <p class="text-muted text-center">Pronto nuevos episodios.</p>
            </div>
          <?php else: ?>
            <?php foreach ($podcastsHome as $p): ?>
              <?php [$embedUrl, $tipo] = media_embed((string) ($p['url_embed'] ?? '')); ?>
              <div class="item">
                <div class="area-box px-1">
                  <div class="media-frame">
                    <?php if ($tipo === 'video'): ?>
                      <div class="embed-responsive embed-responsive-16by9">
                        <iframe class="embed-responsive-item" src="<?= e($embedUrl) ?>" title="<?= e($p['titulo'] ?? 'Podcast') ?>" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; fullscreen" allowfullscreen loading="lazy"></iframe>
                      </div>
                    <?php else: ?>
                      <iframe src="<?= e($embedUrl) ?>" width="100%" height="152" frameBorder="0" allow="autoplay; clipboard-write; encrypted-media; fullscreen; picture-in-picture" loading="lazy" style="border-radius:12px; border:0;" title="<?= e($p['titulo'] ?? 'Podcast') ?>"></iframe>
                    <?php endif; ?>
                  </div>
                  <h5 class="mt-3 mb-1" style="color: #92400e; font-size: 0.85rem;"><?= e($tipo === 'video' ? 'Video' : 'Audio') ?> · <?= e(fecha_comun($p['fecha_publicacion'] ?? null)) ?></h5>
                  <p class="mb-0"><?= e(resumen($p['titulo'] ?? '', 90)) ?></p>
                </div>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
        <div class="text-center mt-4">
          <a href="pages/podcast.php" class="btn btn-style btn-primary">Ver todos</a>
        </div>
      </div>
    </section>
    <!-- //Podcast -->

    <!-- Video -->
    <section class="w3l-team" id="videos">
      <div class="teams1 py-5 mb-3">
        <div class="container py-lg-3 pb-lg-5 pb-4">
          <div class="text-center mb-4">
            <a href="pages/video.php"><h3 class="title-big mb-0">Videos</h3></a>
          </div>
          <div class="owl-media owl-carousel owl-theme">
            <?php if (empty($videosHome)): ?>
              <div class="item">
                <p class="text-muted text-center">Pronto nuevos videos publicados.</p>
              </div>
            <?php else: ?>
              <?php foreach ($videosHome as $v): ?>
                <?php [$embedUrl, $tipo] = media_embed((string) ($v['url_embed'] ?? '')); ?>
                <div class="item">
                  <div class="area-box px-1">
                    <?php if ($tipo === 'video'): ?>
                      <div class="embed-responsive embed-responsive-16by9">
                        <iframe class="embed-responsive-item" src="<?= e($embedUrl) ?>" title="<?= e($v['titulo'] ?? 'Video') ?>" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; fullscreen" allowfullscreen loading="lazy"></iframe>
                      </div>
                    <?php else: ?>
                      <iframe src="<?= e($embedUrl) ?>" width="100%" height="152" frameBorder="0" allow="autoplay; clipboard-write; encrypted-media; fullscreen; picture-in-picture" loading="lazy" style="border-radius:12px; border:0;" title="<?= e($v['titulo'] ?? 'Video') ?>"></iframe>
                    <?php endif; ?>
                    <h5 class="mt-3 mb-1" style="color: #92400e; font-size: 0.85rem;"><?= e($tipo === 'video' ? 'Video' : 'Audio') ?> · <?= e(fecha_comun($v['fecha_publicacion'] ?? null)) ?></h5>
                    <p class="mb-0"><?= e(resumen($v['titulo'] ?? '', 90)) ?></p>
                  </div>
                </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
          <div class="text-center mt-4">
            <a href="pages/video.php" class="btn btn-style btn-primary">Ver todos</a>
          </div>
        </div>
      </div>
    </section>
    <!-- //Video -->

    <!-- Nosotros -->
    <section class="w3l-banner py-0" id="nosotros">
      <div class="midd-w3 py-lg-4 py-md-3" id="alianzas">
        <div class="container">
          <div class="row">
            <div class="col-lg-6 mt-lg-0 mt-lg-5 about-right-faq align-self">
              <h5 class="title-small mb-2">DDP Noticias</h5>
              <h3 class="title-banner">Diálogo y Desarrollo Perú</h3>
              <p class="mt-4">Somos un espacio de periodismo independiente que busca visibilizar las acciones de diálogo en el país desde una mirada constructiva.</p>
              <a href="pages/nosotros.php" class="btn btn-style btn-primary mt-md-5 mt-4">Nosotros</a>
            </div>
            <div class="col-md-6 left-wthree-img mt-lg-0 mt-4">
              <div class="position-relative">
                <img src="assets/images/bannerimg.jpg" alt="Sobre Diálogo y Desarrollo Perú" class="img-fluid" loading="lazy" />
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
    <!-- //Nosotros -->

    <!-- Redes sociales -->
    <div class="middle py-5" id="contacto">
      <div class="container py-xl-5 py-lg-3">
        <div class="welcome-left text-center py-md-5 py-3">
          <h3 class="title-big">Síguenos en nuestras Redes Sociales</h3>
          <div class="main-social-footer-29">
            <a target="_blank" href="https://www.facebook.com/DialogoyDesarrolloPeru" class="facebook"><span class="fa fa-facebook-square fa-2x"></span></a>
            <a target="_blank" href="https://www.tiktok.com/@dialogo.y.desarrollo" class="twitter"><img src="assets/images/tiktokg.png" alt="TikTok" /></a>
            <a target="_blank" href="https://www.instagram.com/dialogo.y.desarrollo/" class="instagram"><span class="fa fa-instagram fa-2x"></span></a>
          </div>
        </div>
      </div>
    </div>
    <!-- //Redes sociales -->

    <!-- footer -->
    <section class="w3l-footer-29-main py-5" id="footer">
      <div class="footer-29 py-md-3">
        <div class="container">
          <div class="row footer-top-29">
            <div class="col-lg-6 col-md-6 footer-list-29 footer-1">
              <h6 class="footer-title-29">Quiénes Somos</h6>
              <p>Somos un espacio de periodismo independiente que busca visibilizar las acciones de diálogo en el país desde una mirada constructiva.</p>
              <div class="main-social-footer-29">
                <a target="_blank" href="https://www.facebook.com/DialogoyDesarrolloPeru" class="facebook"><span class="fa fa-facebook-square"></span></a>
                <a target="_blank" href="https://www.tiktok.com/@dialogo.y.desarrollo" class="twitter"><img src="assets/images/tiktokp.png" alt="TikTok" /></a>
                <a target="_blank" href="https://www.instagram.com/dialogo.y.desarrollo/" class="instagram"><span class="fa fa-instagram"></span></a>
              </div>
            </div>
            <div class="col-lg-3 col-md-6 footer-list-29 footer-2 mt-md-0 mt-5">
              <ul>
                <h6 class="footer-title-29">Contenido</h6>
                <li><a href="#actualidad">Noticias</a></li>
                <li><a href="pages/reportajes.php">Reportajes</a></li>
                <li><a href="pages/podcast.php">Podcast</a></li>
                <li><a href="pages/video.php">Video</a></li>
              </ul>
            </div>
            <div class="col-lg-3 col-md-6 mt-lg-0 mt-5 footer-list-29 footer-3">
              <div class="properties">
                <h6 class="footer-title-29">Contacto</h6>
                <ul>
                  <li><a href="mailto:info@dialogoydesarrollo.com.pe">info@dialogoydesarrollo.com.pe</a></li>
                </ul>
              </div>
            </div>
          </div>
          <div class="bottom-copies text-center">
            <p class="copy-footer-29">© <?= date('Y'); ?> Diálogo y Desarrollo Perú. All rights reserved | Designed by <a target="_blank" href="https://www.wsperu.info">WebSolutions</a></p>
          </div>
        </div>
      </div>
      <button onclick="topFunction()" id="movetop" title="Go to top">
        <span class="fa fa-angle-up"></span>
      </button>
      <script>
        window.onscroll = function () {
          scrollFunction()
        };

        function scrollFunction() {
          if (document.body.scrollTop > 20 || document.documentElement.scrollTop > 20) {
            document.getElementById("movetop").style.display = "block";
          } else {
            document.getElementById("movetop").style.display = "none";
          }
        }

        function topFunction() {
          document.body.scrollTop = 0;
          document.documentElement.scrollTop = 0;
        }
      </script>
    </section>
    <!-- //footer -->

    <!-- Template JavaScript -->
    <script src="assets/js/jquery-3.3.1.min.js"></script>
    <script src="assets/js/theme-change.js"></script>
    <script src="assets/js/owl.carousel.js"></script>
    <script>
      $(document).ready(function () {
        $('.owl-carousel').owlCarousel({
          loop: true,
          margin: 0,
          nav: true,
          responsiveClass: true,
          responsive: {
            0: { items: 1, margin: 10 },
            768: { items: 2, margin: 20 },
            1000: { items: 2, margin: 25 }
          }
        })
      })
    </script>
    <script>
      $(window).on("scroll", function () {
        var scroll = $(window).scrollTop();
        if (scroll >= 80) {
          $("#site-header").addClass("nav-fixed");
        } else {
          $("#site-header").removeClass("nav-fixed");
        }
      });
      $(".navbar-toggler").on("click", function () {
        $("header").toggleClass("active");
      });
      $(document).on("ready", function () {
        if ($(window).width() > 991) {
          $("header").removeClass("active");
        }
        $(window).on("resize", function () {
          if ($(window).width() > 991) {
            $("header").removeClass("active");
          }
        });
      });
    </script>
    <script>
      function ddpScrollActualidad() {
        var el = document.getElementById("actualidad");
        if (el) {
          var y = el.getBoundingClientRect().top + window.pageYOffset - 105;
          if ("scrollBehavior" in document.documentElement.style) {
            window.scrollTo({ top: y, behavior: "smooth" });
          } else {
            window.scrollTo(0, y);
          }
        }
      }
      $("a[href=\"#actualidad\"]").on("click", function (e) {
        e.preventDefault();
        ddpScrollActualidad();
      });
      if (location.hash === "#actualidad") {
        $(window).on("load", function () {
          ddpScrollActualidad();
        });
      }
    </script>
    <script src="assets/js/bootstrap.min.js"></script>
  </body>
</html>